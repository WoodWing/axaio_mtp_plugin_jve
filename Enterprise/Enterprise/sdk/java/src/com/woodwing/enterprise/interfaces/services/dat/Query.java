/**
 * Query.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.dat;

public class Query  implements java.io.Serializable {
    private java.lang.String ID;

    private java.lang.String name;

    private com.woodwing.enterprise.interfaces.services.dat.Property[] params;

    private java.lang.String recordFamily;

    public Query() {
    }

    public Query(
           java.lang.String ID,
           java.lang.String name,
           com.woodwing.enterprise.interfaces.services.dat.Property[] params,
           java.lang.String recordFamily) {
           this.ID = ID;
           this.name = name;
           this.params = params;
           this.recordFamily = recordFamily;
    }


    /**
     * Gets the ID value for this Query.
     * 
     * @return ID
     */
    public java.lang.String getID() {
        return ID;
    }


    /**
     * Sets the ID value for this Query.
     * 
     * @param ID
     */
    public void setID(java.lang.String ID) {
        this.ID = ID;
    }


    /**
     * Gets the name value for this Query.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this Query.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the params value for this Query.
     * 
     * @return params
     */
    public com.woodwing.enterprise.interfaces.services.dat.Property[] getParams() {
        return params;
    }


    /**
     * Sets the params value for this Query.
     * 
     * @param params
     */
    public void setParams(com.woodwing.enterprise.interfaces.services.dat.Property[] params) {
        this.params = params;
    }


    /**
     * Gets the recordFamily value for this Query.
     * 
     * @return recordFamily
     */
    public java.lang.String getRecordFamily() {
        return recordFamily;
    }


    /**
     * Sets the recordFamily value for this Query.
     * 
     * @param recordFamily
     */
    public void setRecordFamily(java.lang.String recordFamily) {
        this.recordFamily = recordFamily;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Query)) return false;
        Query other = (Query) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ID==null && other.getID()==null) || 
             (this.ID!=null &&
              this.ID.equals(other.getID()))) &&
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            ((this.params==null && other.getParams()==null) || 
             (this.params!=null &&
              java.util.Arrays.equals(this.params, other.getParams()))) &&
            ((this.recordFamily==null && other.getRecordFamily()==null) || 
             (this.recordFamily!=null &&
              this.recordFamily.equals(other.getRecordFamily())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getID() != null) {
            _hashCode += getID().hashCode();
        }
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        if (getParams() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getParams());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getParams(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getRecordFamily() != null) {
            _hashCode += getRecordFamily().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Query.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "Query"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("params");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Params"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "Property"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Property"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("recordFamily");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RecordFamily"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
