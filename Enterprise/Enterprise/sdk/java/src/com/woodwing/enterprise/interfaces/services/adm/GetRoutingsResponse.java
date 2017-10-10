/**
 * GetRoutingsResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class GetRoutingsResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.adm.Routing[] routings;

    private com.woodwing.enterprise.interfaces.services.adm.Section[] sections;

    private com.woodwing.enterprise.interfaces.services.adm.Status[] statuses;

    public GetRoutingsResponse() {
    }

    public GetRoutingsResponse(
           com.woodwing.enterprise.interfaces.services.adm.Routing[] routings,
           com.woodwing.enterprise.interfaces.services.adm.Section[] sections,
           com.woodwing.enterprise.interfaces.services.adm.Status[] statuses) {
           this.routings = routings;
           this.sections = sections;
           this.statuses = statuses;
    }


    /**
     * Gets the routings value for this GetRoutingsResponse.
     * 
     * @return routings
     */
    public com.woodwing.enterprise.interfaces.services.adm.Routing[] getRoutings() {
        return routings;
    }


    /**
     * Sets the routings value for this GetRoutingsResponse.
     * 
     * @param routings
     */
    public void setRoutings(com.woodwing.enterprise.interfaces.services.adm.Routing[] routings) {
        this.routings = routings;
    }


    /**
     * Gets the sections value for this GetRoutingsResponse.
     * 
     * @return sections
     */
    public com.woodwing.enterprise.interfaces.services.adm.Section[] getSections() {
        return sections;
    }


    /**
     * Sets the sections value for this GetRoutingsResponse.
     * 
     * @param sections
     */
    public void setSections(com.woodwing.enterprise.interfaces.services.adm.Section[] sections) {
        this.sections = sections;
    }


    /**
     * Gets the statuses value for this GetRoutingsResponse.
     * 
     * @return statuses
     */
    public com.woodwing.enterprise.interfaces.services.adm.Status[] getStatuses() {
        return statuses;
    }


    /**
     * Sets the statuses value for this GetRoutingsResponse.
     * 
     * @param statuses
     */
    public void setStatuses(com.woodwing.enterprise.interfaces.services.adm.Status[] statuses) {
        this.statuses = statuses;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetRoutingsResponse)) return false;
        GetRoutingsResponse other = (GetRoutingsResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.routings==null && other.getRoutings()==null) || 
             (this.routings!=null &&
              java.util.Arrays.equals(this.routings, other.getRoutings()))) &&
            ((this.sections==null && other.getSections()==null) || 
             (this.sections!=null &&
              java.util.Arrays.equals(this.sections, other.getSections()))) &&
            ((this.statuses==null && other.getStatuses()==null) || 
             (this.statuses!=null &&
              java.util.Arrays.equals(this.statuses, other.getStatuses())));
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
        if (getRoutings() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRoutings());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRoutings(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getSections() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getSections());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getSections(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getStatuses() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getStatuses());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getStatuses(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetRoutingsResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">GetRoutingsResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("routings");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Routings"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Routing"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Routing"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sections");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Sections"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Section"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Section"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("statuses");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Statuses"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Status"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Status"));
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
