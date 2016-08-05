/**
 * CreatePublicationsResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.adm;

public class CreatePublicationsResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.adm.Publication[] publications;

    public CreatePublicationsResponse() {
    }

    public CreatePublicationsResponse(
           com.woodwing.enterprise.interfaces.services.adm.Publication[] publications) {
           this.publications = publications;
    }


    /**
     * Gets the publications value for this CreatePublicationsResponse.
     * 
     * @return publications
     */
    public com.woodwing.enterprise.interfaces.services.adm.Publication[] getPublications() {
        return publications;
    }


    /**
     * Sets the publications value for this CreatePublicationsResponse.
     * 
     * @param publications
     */
    public void setPublications(com.woodwing.enterprise.interfaces.services.adm.Publication[] publications) {
        this.publications = publications;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof CreatePublicationsResponse)) return false;
        CreatePublicationsResponse other = (CreatePublicationsResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.publications==null && other.getPublications()==null) || 
             (this.publications!=null &&
              java.util.Arrays.equals(this.publications, other.getPublications())));
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
        if (getPublications() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPublications());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPublications(), i);
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
        new org.apache.axis.description.TypeDesc(CreatePublicationsResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", ">CreatePublicationsResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publications");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Publications"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnectionAdmin", "Publication"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Publication"));
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