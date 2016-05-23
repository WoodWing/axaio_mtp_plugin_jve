/**
 * DeleteObjects.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class DeleteObjects  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String[] IDs;

    private boolean permanent;

    private com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] params;

    private com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas;

    private java.lang.String context;

    public DeleteObjects() {
    }

    public DeleteObjects(
           java.lang.String ticket,
           java.lang.String[] IDs,
           boolean permanent,
           com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] params,
           com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas,
           java.lang.String context) {
           this.ticket = ticket;
           this.IDs = IDs;
           this.permanent = permanent;
           this.params = params;
           this.areas = areas;
           this.context = context;
    }


    /**
     * Gets the ticket value for this DeleteObjects.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this DeleteObjects.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the IDs value for this DeleteObjects.
     * 
     * @return IDs
     */
    public java.lang.String[] getIDs() {
        return IDs;
    }


    /**
     * Sets the IDs value for this DeleteObjects.
     * 
     * @param IDs
     */
    public void setIDs(java.lang.String[] IDs) {
        this.IDs = IDs;
    }


    /**
     * Gets the permanent value for this DeleteObjects.
     * 
     * @return permanent
     */
    public boolean isPermanent() {
        return permanent;
    }


    /**
     * Sets the permanent value for this DeleteObjects.
     * 
     * @param permanent
     */
    public void setPermanent(boolean permanent) {
        this.permanent = permanent;
    }


    /**
     * Gets the params value for this DeleteObjects.
     * 
     * @return params
     */
    public com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] getParams() {
        return params;
    }


    /**
     * Sets the params value for this DeleteObjects.
     * 
     * @param params
     */
    public void setParams(com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] params) {
        this.params = params;
    }


    /**
     * Gets the areas value for this DeleteObjects.
     * 
     * @return areas
     */
    public com.woodwing.enterprise.interfaces.services.wfl.AreaType[] getAreas() {
        return areas;
    }


    /**
     * Sets the areas value for this DeleteObjects.
     * 
     * @param areas
     */
    public void setAreas(com.woodwing.enterprise.interfaces.services.wfl.AreaType[] areas) {
        this.areas = areas;
    }


    /**
     * Gets the context value for this DeleteObjects.
     * 
     * @return context
     */
    public java.lang.String getContext() {
        return context;
    }


    /**
     * Sets the context value for this DeleteObjects.
     * 
     * @param context
     */
    public void setContext(java.lang.String context) {
        this.context = context;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof DeleteObjects)) return false;
        DeleteObjects other = (DeleteObjects) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            ((this.IDs==null && other.getIDs()==null) || 
             (this.IDs!=null &&
              java.util.Arrays.equals(this.IDs, other.getIDs()))) &&
            this.permanent == other.isPermanent() &&
            ((this.params==null && other.getParams()==null) || 
             (this.params!=null &&
              java.util.Arrays.equals(this.params, other.getParams()))) &&
            ((this.areas==null && other.getAreas()==null) || 
             (this.areas!=null &&
              java.util.Arrays.equals(this.areas, other.getAreas()))) &&
            ((this.context==null && other.getContext()==null) || 
             (this.context!=null &&
              this.context.equals(other.getContext())));
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
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        if (getIDs() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getIDs());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getIDs(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        _hashCode += (isPermanent() ? Boolean.TRUE : Boolean.FALSE).hashCode();
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
        if (getAreas() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getAreas());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getAreas(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getContext() != null) {
            _hashCode += getContext().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(DeleteObjects.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">DeleteObjects"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("IDs");
        elemField.setXmlName(new javax.xml.namespace.QName("", "IDs"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("permanent");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Permanent"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("params");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Params"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "QueryParam"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "QueryParam"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("areas");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Areas"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "AreaType"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "AreaType"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("context");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Context"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
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
